<template>
<view>
	<block v-for="(setData, index) in pagecontent" :key="index">
		<block v-if="setData.temp=='notice'">
			<dp-notice :params="setData.params" :data="setData.data"></dp-notice>
		</block>
		<block v-if="setData.temp=='banner'">
			<dp-banner :params="setData.params" :data="setData.data" @banner-change="$emit('banner-change', $event)"></dp-banner> 
		</block>
		<block v-if="setData.temp=='search'">
			<dp-search :params="setData.params" :data="setData.data"></dp-search>
		</block>

		<block v-if="setData.temp=='text'">
			<dp-text :params="setData.params" :data="setData.data" :data-url='setData.params.hrefurl'></dp-text>
		</block>
		<block v-if="setData.temp=='title'">
			<dp-title :params="setData.params" :data="setData.data"></dp-title>
		</block>
		<block v-if="setData.temp=='dhlist'">
			<dp-dhlist :params="setData.params" :data="setData.data"></dp-dhlist>
		</block>
		<block v-if="setData.temp=='line'">
			<dp-line :params="setData.params" :data="setData.data"></dp-line>
		</block>
		<block v-if="setData.temp=='blank'">
			<dp-blank :params="setData.params" :data="setData.data"></dp-blank>
		</block>
		<block v-if="setData.temp=='menu'">
			<dp-menu :params="setData.params" :data="setData.data" :isapplymendian="isapplymendian"></dp-menu> 
		</block>
		<block v-if="setData.temp=='map'">
			<dp-map :params="setData.params" :data="setData.data"></dp-map> 
		</block>
		<block v-if="setData.temp=='cube'">
			<dp-cube :params="setData.params" :data="setData.data" :tabcub="{}"></dp-cube> 
		</block>
		<block v-if="setData.temp=='picture'">
			<dp-picture :params="setData.params" :data="setData.data"></dp-picture> 
		</block>
		<block v-if="setData.temp=='pictures'"> 
			<dp-pictures :params="setData.params" :data="setData.data"></dp-pictures> 
		</block>
		<block v-if="setData.temp=='video'">
			<dp-video :params="setData.params" :data="setData.data"></dp-video> 
		</block>
		<block v-if="setData.temp=='tab'">
			<dp-tab :params="setData.params" :data="setData.data" :tabid="setData.id" :menuindex="menuindex" @getdata="getdata" :richtype="richtype" :richurl="richurl" :navigationHieght='navigationHieght'></dp-tab> 
		</block>
		<block v-if="setData.temp=='shop'">
			<dp-shop :params="setData.params" :data="setData.data" :shopinfo="setData.shopinfo"></dp-shop> 
		</block>
		<block v-if="setData.temp=='product'">
			<dp-product :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-product> 
		</block>
		<block v-if="setData.temp=='collage'">
			<dp-collage :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-collage> 
		</block>
		<block v-if="setData.temp=='luckycollage'">
			<dp-luckycollage :params="setData.params" :data="setData.data"></dp-luckycollage> 
		</block>

		<block v-if="setData.temp=='kanjia'">
			<dp-kanjia :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-kanjia> 
		</block>
		<block v-if="setData.temp=='yuyue'">
			<dp-yuyue :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-yuyue>
		</block>
		<block v-if="setData.temp=='seckill'">
			<dp-seckill :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-seckill> 
		</block>
		<block v-if="setData.temp=='scoreshop'">
			<dp-scoreshop :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-scoreshop> 
		</block>
		<block v-if="setData.temp=='tuangou'">
			<dp-tuangou :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-tuangou> 
		</block>
		<block v-if="setData.temp=='kecheng'">
			<dp-kecheng :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-kecheng> 
		</block>

		<block v-if="setData.temp=='coupon'">
			<dp-coupon :params="setData.params" :data="setData.data"></dp-coupon> 
		</block>
		<block v-if="setData.temp=='article'">
			<dp-article :params="setData.params" :data="setData.data"></dp-article> 
		</block>
		<block v-if="setData.temp=='business'">
			<dp-business :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-business> 
		</block>

		<block v-if="setData.temp=='button'">
			<dp-button :params="setData.params" :data="setData.data"></dp-button> 
		</block>
		<block v-if="setData.temp=='hotspot'">
			<dp-hotspot :params="setData.params" :data="setData.data"></dp-hotspot> 
		</block>
		<block v-if="setData.temp=='cover'">
			<dp-cover :params="setData.params" :data="setData.data"></dp-cover> 
		</block>
		<block v-if="setData.temp=='richtext'">
			<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content" :richtype="richtype" :richurl="richurl"></dp-richtext>
		</block>
		<block v-if="setData.temp=='form'">
			<dp-form :params="setData.params" :data="setData.data" :content="setData.content" :latitude="latitude" :longitude="longitude" :htsignatureurl="htsignatureurl"></dp-form>
		</block>

		<block v-if="setData.temp=='userinfo'">
			<dp-userinfo :params="setData.params" :data="setData.data" :content="setData.content"></dp-userinfo> 
		</block>
		<block v-if="setData.temp=='wxad'">
			<dp-wxad :params="setData.params" :data="setData.data"></dp-wxad> 
		</block>
		<!-- #ifdef APP-PLUS -->
		<block v-if="setData.temp=='adset'">
			<dp-adset :params="setData.params" :data="setData.data"></dp-adset> 
		</block>
		<!-- #endif -->

		<block v-if="setData.temp=='photo_generation'">
			<dp-photo-generation :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-photo-generation> 
		</block>
		<block v-if="setData.temp=='video_generation'">
			<dp-video-generation :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-video-generation> 
		</block>
		<block v-if="setData.temp=='camera'">
			<uni-image-upload :params="setData.params" :maxCount="parseInt(setData.params.max_count) || 9" :maxSize="parseInt(setData.params.max_size) || 10" :columns="parseInt(setData.params.columns) || 4" :cameraPosition="setData.params.device_position || 'back'" :enableAlbum="setData.params.enable_album !== false" :enableChat="setData.params.enable_chat !== false"></uni-image-upload>
		</block>
	</block>
</view>
</template>
<script>
	export default {
		props: {
			menuindex:{default:-1},
			pagecontent:{},
			latitude:'',
			longitude:'',
      richtype:{default:0},
      richurl:{default:''},
			navigationHieght:{default:0},
			isapplymendian:{default:0},
      htsignatureurl:'',
		},
        methods: {
            getdata:function(){
                this.$emit('getdata');
            }
        }
	}
</script>